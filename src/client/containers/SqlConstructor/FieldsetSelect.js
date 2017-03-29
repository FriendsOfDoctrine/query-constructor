import { connect } from 'react-redux'
import FieldsetSelect from '../../components/FieldsetSelect'
import { loadProperties, setValue } from '../../redux/modules/select'
import { bindAllTo } from '../../util/helpers'


const mapStateToProps = (state) => {
  return {
    data: { ...state.select.data },
    values: { ...state.select.values }
  };
};

const mapDispatchToProps = { loadProperties, setValue };

export default connect(
  mapStateToProps,
  mapDispatchToProps
)(FieldsetSelect);
